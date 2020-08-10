using RebirthTracker.PacketHandlers;
using System;
using System.Collections.Generic;
using System.Linq;

namespace RebirthTracker
{
    /// <summary>
    /// A factory to get a packet handler for an opcode
    /// </summary>
    public class PacketHandlerFactory
    {
        private Dictionary<byte, Type> packetHandlers;

        /// <summary>
        /// Constructor registers all classes that implement the IPacketHandler interface
        /// </summary>
        public PacketHandlerFactory()
        {
            packetHandlers = new Dictionary<byte, Type>();

            var packetHandlerTypes = AppDomain.CurrentDomain.GetAssemblies()
                .SelectMany(s => s.GetTypes())
                .Where(type => type.IsClass && type.GetInterfaces().Contains(typeof(IPacketHandler)));

            foreach (var packerHandlerType in packetHandlerTypes)
            {
                var opcodeAttribute = (OpcodeAttribute) packerHandlerType.GetCustomAttributes(typeof(OpcodeAttribute), false).First();

                packetHandlers.Add(opcodeAttribute.Opcode, packerHandlerType);
            }
        }

        /// <summary>
        /// Get the packet handler for the given opcode
        /// </summary>
        public IPacketHandler GetPacketHandler(byte opcode)
        {
            Type packetHandler;
            packetHandlers.TryGetValue(opcode, out packetHandler);

            if (packetHandler != null)
            {
                return (IPacketHandler) Activator.CreateInstance(packetHandler);
            }

            throw new ArgumentOutOfRangeException("Unrecognized opcode - skipping");
        }
    }
}
